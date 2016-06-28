/**
 * GetDialog2.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetDialog2  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.wfl.Action action;

    private com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    private java.lang.String defaultDossier;

    private java.lang.String parent;

    private java.lang.String template;

    private com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas;

    private java.lang.Boolean multipleObjects;

    public GetDialog2() {
    }

    public GetDialog2(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.wfl.Action action,
           com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets,
           java.lang.String defaultDossier,
           java.lang.String parent,
           java.lang.String template,
           com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas,
           java.lang.Boolean multipleObjects) {
           this.ticket = ticket;
           this.action = action;
           this.metaData = metaData;
           this.targets = targets;
           this.defaultDossier = defaultDossier;
           this.parent = parent;
           this.template = template;
           this.areas = areas;
           this.multipleObjects = multipleObjects;
    }


    /**
     * Gets the ticket value for this GetDialog2.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetDialog2.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the action value for this GetDialog2.
     * 
     * @return action
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Action getAction() {
        return action;
    }


    /**
     * Sets the action value for this GetDialog2.
     * 
     * @param action
     */
    public void setAction(com.woodwing.enterprise.interfaces.services.wfl.Action action) {
        this.action = action;
    }


    /**
     * Gets the metaData value for this GetDialog2.
     * 
     * @return metaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] getMetaData() {
        return metaData;
    }


    /**
     * Sets the metaData value for this GetDialog2.
     * 
     * @param metaData
     */
    public void setMetaData(com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData) {
        this.metaData = metaData;
    }


    /**
     * Gets the targets value for this GetDialog2.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this GetDialog2.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }


    /**
     * Gets the defaultDossier value for this GetDialog2.
     * 
     * @return defaultDossier
     */
    public java.lang.String getDefaultDossier() {
        return defaultDossier;
    }


    /**
     * Sets the defaultDossier value for this GetDialog2.
     * 
     * @param defaultDossier
     */
    public void setDefaultDossier(java.lang.String defaultDossier) {
        this.defaultDossier = defaultDossier;
    }


    /**
     * Gets the parent value for this GetDialog2.
     * 
     * @return parent
     */
    public java.lang.String getParent() {
        return parent;
    }


    /**
     * Sets the parent value for this GetDialog2.
     * 
     * @param parent
     */
    public void setParent(java.lang.String parent) {
        this.parent = parent;
    }


    /**
     * Gets the template value for this GetDialog2.
     * 
     * @return template
     */
    public java.lang.String getTemplate() {
        return template;
    }


    /**
     * Sets the template value for this GetDialog2.
     * 
     * @param template
     */
    public void setTemplate(java.lang.String template) {
        this.template = template;
    }


    /**
     * Gets the areas value for this GetDialog2.
     * 
     * @return areas
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AreaType[] getAreas() {
        return areas;
    }


    /**
     * Sets the areas value for this GetDialog2.
     * 
     * @param areas
     */
    public void setAreas(com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
        this.areas = areas;
    }


    /**
     * Gets the multipleObjects value for this GetDialog2.
     * 
     * @return multipleObjects
     */
    public java.lang.Boolean getMultipleObjects() {
        return multipleObjects;
    }


    /**
     * Sets the multipleObjects value for this GetDialog2.
     * 
     * @param multipleObjects
     */
    public void setMultipleObjects(java.lang.Boolean multipleObjects) {
        this.multipleObjects = multipleObjects;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDialog2)) return false;
        GetDialog2 other = (GetDialog2) obj;
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
            ((this.action==null && other.getAction()==null) || 
             (this.action!=null &&
              this.action.equals(other.getAction()))) &&
            ((this.metaData==null && other.getMetaData()==null) || 
             (this.metaData!=null &&
              java.util.Arrays.equals(this.metaData, other.getMetaData()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets()))) &&
            ((this.defaultDossier==null && other.getDefaultDossier()==null) || 
             (this.defaultDossier!=null &&
              this.defaultDossier.equals(other.getDefaultDossier()))) &&
            ((this.parent==null && other.getParent()==null) || 
             (this.parent!=null &&
              this.parent.equals(other.getParent()))) &&
            ((this.template==null && other.getTemplate()==null) || 
             (this.template!=null &&
              this.template.equals(other.getTemplate()))) &&
            ((this.areas==null && other.getAreas()==null) || 
             (this.areas!=null &&
              java.util.Arrays.equals(this.areas, other.getAreas()))) &&
            ((this.multipleObjects==null && other.getMultipleObjects()==null) || 
             (this.multipleObjects!=null &&
              this.multipleObjects.equals(other.getMultipleObjects())));
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
        if (getAction() != null) {
            _hashCode += getAction().hashCode();
        }
        if (getMetaData() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMetaData());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMetaData(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTargets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getDefaultDossier() != null) {
            _hashCode += getDefaultDossier().hashCode();
        }
        if (getParent() != null) {
            _hashCode += getParent().hashCode();
        }
        if (getTemplate() != null) {
            _hashCode += getTemplate().hashCode();
        }
        if (getAreas() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAreas());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAreas(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMultipleObjects() != null) {
            _hashCode += getMultipleObjects().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetDialog2.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog2"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("action");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Action"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Action"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("metaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaDataValue"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "MetaDataValue"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Targets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Target"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Target"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("defaultDossier");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DefaultDossier"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("parent");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Parent"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("template");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Template"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("areas");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Areas"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AreaType"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AreaType"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("multipleObjects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MultipleObjects"));
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
