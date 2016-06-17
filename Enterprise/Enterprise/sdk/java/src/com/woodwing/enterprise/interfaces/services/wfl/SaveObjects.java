/**
 * SaveObjects.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class SaveObjects  implements java.io.Serializable {
    private java.lang.String ticket;

    private boolean createVersion;

    private boolean forceCheckIn;

    private boolean unlock;

    private com.woodwing.enterprise.interfaces.services.wfl.Object[] objects;

    private java.lang.String[] readMessageIDs;

    private com.woodwing.enterprise.interfaces.services.wfl.Message[] messages;

    public SaveObjects() {
    }

    public SaveObjects(
           java.lang.String ticket,
           boolean createVersion,
           boolean forceCheckIn,
           boolean unlock,
           com.woodwing.enterprise.interfaces.services.wfl.Object[] objects,
           java.lang.String[] readMessageIDs,
           com.woodwing.enterprise.interfaces.services.wfl.Message[] messages) {
           this.ticket = ticket;
           this.createVersion = createVersion;
           this.forceCheckIn = forceCheckIn;
           this.unlock = unlock;
           this.objects = objects;
           this.readMessageIDs = readMessageIDs;
           this.messages = messages;
    }


    /**
     * Gets the ticket value for this SaveObjects.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this SaveObjects.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the createVersion value for this SaveObjects.
     * 
     * @return createVersion
     */
    public boolean isCreateVersion() {
        return createVersion;
    }


    /**
     * Sets the createVersion value for this SaveObjects.
     * 
     * @param createVersion
     */
    public void setCreateVersion(boolean createVersion) {
        this.createVersion = createVersion;
    }


    /**
     * Gets the forceCheckIn value for this SaveObjects.
     * 
     * @return forceCheckIn
     */
    public boolean isForceCheckIn() {
        return forceCheckIn;
    }


    /**
     * Sets the forceCheckIn value for this SaveObjects.
     * 
     * @param forceCheckIn
     */
    public void setForceCheckIn(boolean forceCheckIn) {
        this.forceCheckIn = forceCheckIn;
    }


    /**
     * Gets the unlock value for this SaveObjects.
     * 
     * @return unlock
     */
    public boolean isUnlock() {
        return unlock;
    }


    /**
     * Sets the unlock value for this SaveObjects.
     * 
     * @param unlock
     */
    public void setUnlock(boolean unlock) {
        this.unlock = unlock;
    }


    /**
     * Gets the objects value for this SaveObjects.
     * 
     * @return objects
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Object[] getObjects() {
        return objects;
    }


    /**
     * Sets the objects value for this SaveObjects.
     * 
     * @param objects
     */
    public void setObjects(com.woodwing.enterprise.interfaces.services.wfl.Object[] objects) {
        this.objects = objects;
    }


    /**
     * Gets the readMessageIDs value for this SaveObjects.
     * 
     * @return readMessageIDs
     */
    public java.lang.String[] getReadMessageIDs() {
        return readMessageIDs;
    }


    /**
     * Sets the readMessageIDs value for this SaveObjects.
     * 
     * @param readMessageIDs
     */
    public void setReadMessageIDs(java.lang.String[] readMessageIDs) {
        this.readMessageIDs = readMessageIDs;
    }


    /**
     * Gets the messages value for this SaveObjects.
     * 
     * @return messages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Message[] getMessages() {
        return messages;
    }


    /**
     * Sets the messages value for this SaveObjects.
     * 
     * @param messages
     */
    public void setMessages(com.woodwing.enterprise.interfaces.services.wfl.Message[] messages) {
        this.messages = messages;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof SaveObjects)) return false;
        SaveObjects other = (SaveObjects) obj;
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
            this.createVersion == other.isCreateVersion() &&
            this.forceCheckIn == other.isForceCheckIn() &&
            this.unlock == other.isUnlock() &&
            ((this.objects==null && other.getObjects()==null) || 
             (this.objects!=null &&
              java.util.Arrays.equals(this.objects, other.getObjects()))) &&
            ((this.readMessageIDs==null && other.getReadMessageIDs()==null) || 
             (this.readMessageIDs!=null &&
              java.util.Arrays.equals(this.readMessageIDs, other.getReadMessageIDs()))) &&
            ((this.messages==null && other.getMessages()==null) || 
             (this.messages!=null &&
              java.util.Arrays.equals(this.messages, other.getMessages())));
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
        _hashCode += (isCreateVersion() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isForceCheckIn() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isUnlock() ? Boolean.TRUE : Boolean.FALSE).hashCode();
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
        if (getReadMessageIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getReadMessageIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getReadMessageIDs(), i);
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
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(SaveObjects.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">SaveObjects"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("createVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CreateVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("forceCheckIn");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ForceCheckIn"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("unlock");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Unlock"));
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
        elemField.setFieldName("readMessageIDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReadMessageIDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Messages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Message"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Message"));
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
