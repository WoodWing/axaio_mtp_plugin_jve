/**
 * CreateObjects.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class CreateObjects  implements java.io.Serializable {
    private java.lang.String ticket;

    private boolean lock;

    private com.woodwing.enterprise.interfaces.services.wfl.Object[] objects;

    private com.woodwing.enterprise.interfaces.services.wfl.Message[] messages;

    private java.lang.Boolean autoNaming;

    private java.lang.Boolean replaceGUIDs;

    public CreateObjects() {
    }

    public CreateObjects(
           java.lang.String ticket,
           boolean lock,
           com.woodwing.enterprise.interfaces.services.wfl.Object[] objects,
           com.woodwing.enterprise.interfaces.services.wfl.Message[] messages,
           java.lang.Boolean autoNaming,
           java.lang.Boolean replaceGUIDs) {
           this.ticket = ticket;
           this.lock = lock;
           this.objects = objects;
           this.messages = messages;
           this.autoNaming = autoNaming;
           this.replaceGUIDs = replaceGUIDs;
    }


    /**
     * Gets the ticket value for this CreateObjects.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CreateObjects.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the lock value for this CreateObjects.
     * 
     * @return lock
     */
    public boolean isLock() {
        return lock;
    }


    /**
     * Sets the lock value for this CreateObjects.
     * 
     * @param lock
     */
    public void setLock(boolean lock) {
        this.lock = lock;
    }


    /**
     * Gets the objects value for this CreateObjects.
     * 
     * @return objects
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Object[] getObjects() {
        return objects;
    }


    /**
     * Sets the objects value for this CreateObjects.
     * 
     * @param objects
     */
    public void setObjects(com.woodwing.enterprise.interfaces.services.wfl.Object[] objects) {
        this.objects = objects;
    }


    /**
     * Gets the messages value for this CreateObjects.
     * 
     * @return messages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Message[] getMessages() {
        return messages;
    }


    /**
     * Sets the messages value for this CreateObjects.
     * 
     * @param messages
     */
    public void setMessages(com.woodwing.enterprise.interfaces.services.wfl.Message[] messages) {
        this.messages = messages;
    }


    /**
     * Gets the autoNaming value for this CreateObjects.
     * 
     * @return autoNaming
     */
    public java.lang.Boolean getAutoNaming() {
        return autoNaming;
    }


    /**
     * Sets the autoNaming value for this CreateObjects.
     * 
     * @param autoNaming
     */
    public void setAutoNaming(java.lang.Boolean autoNaming) {
        this.autoNaming = autoNaming;
    }


    /**
     * Gets the replaceGUIDs value for this CreateObjects.
     * 
     * @return replaceGUIDs
     */
    public java.lang.Boolean getReplaceGUIDs() {
        return replaceGUIDs;
    }


    /**
     * Sets the replaceGUIDs value for this CreateObjects.
     * 
     * @param replaceGUIDs
     */
    public void setReplaceGUIDs(java.lang.Boolean replaceGUIDs) {
        this.replaceGUIDs = replaceGUIDs;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreateObjects)) return false;
        CreateObjects other = (CreateObjects) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            this.lock == other.isLock() &&
            ((this.objects==null && other.getObjects()==null) || 
             (this.objects!=null &&
              java.util.Arrays.equals(this.objects, other.getObjects()))) &&
            ((this.messages==null && other.getMessages()==null) || 
             (this.messages!=null &&
              java.util.Arrays.equals(this.messages, other.getMessages()))) &&
            ((this.autoNaming==null && other.getAutoNaming()==null) || 
             (this.autoNaming!=null &&
              this.autoNaming.equals(other.getAutoNaming()))) &&
            ((this.replaceGUIDs==null && other.getReplaceGUIDs()==null) || 
             (this.replaceGUIDs!=null &&
              this.replaceGUIDs.equals(other.getReplaceGUIDs())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        _hashCode += (isLock() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjects(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMessages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMessages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getAutoNaming() != null) {
            _hashCode += getAutoNaming().hashCode();
        }
        if (getReplaceGUIDs() != null) {
            _hashCode += getReplaceGUIDs().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(CreateObjects.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjects"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lock");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Lock"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Objects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Object"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Object"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Messages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Message"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Message"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("autoNaming");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AutoNaming"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("replaceGUIDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReplaceGUIDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
