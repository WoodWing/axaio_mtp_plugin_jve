/**
 * GetObjects.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetObjects  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String[] IDs;

    private boolean lock;

    private com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition;

    private java.lang.String[] requestInfo;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion[] haveVersions;

    private com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas;

    private java.lang.String editionId;

    private java.lang.String[] supportedContentSources;

    public GetObjects() {
    }

    public GetObjects(
           java.lang.String ticket,
           java.lang.String[] IDs,
           boolean lock,
           com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition,
           java.lang.String[] requestInfo,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion[] haveVersions,
           com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas,
           java.lang.String editionId,
           java.lang.String[] supportedContentSources) {
           this.ticket = ticket;
           this.IDs = IDs;
           this.lock = lock;
           this.rendition = rendition;
           this.requestInfo = requestInfo;
           this.haveVersions = haveVersions;
           this.areas = areas;
           this.editionId = editionId;
           this.supportedContentSources = supportedContentSources;
    }


    /**
     * Gets the ticket value for this GetObjects.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetObjects.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the IDs value for this GetObjects.
     * 
     * @return IDs
     */
    public java.lang.String[] getIDs() {
        return IDs;
    }


    /**
     * Sets the IDs value for this GetObjects.
     * 
     * @param IDs
     */
    public void setIDs(java.lang.String[] IDs) {
        this.IDs = IDs;
    }


    /**
     * Gets the lock value for this GetObjects.
     * 
     * @return lock
     */
    public boolean isLock() {
        return lock;
    }


    /**
     * Sets the lock value for this GetObjects.
     * 
     * @param lock
     */
    public void setLock(boolean lock) {
        this.lock = lock;
    }


    /**
     * Gets the rendition value for this GetObjects.
     * 
     * @return rendition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionType getRendition() {
        return rendition;
    }


    /**
     * Sets the rendition value for this GetObjects.
     * 
     * @param rendition
     */
    public void setRendition(com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition) {
        this.rendition = rendition;
    }


    /**
     * Gets the requestInfo value for this GetObjects.
     * 
     * @return requestInfo
     */
    public java.lang.String[] getRequestInfo() {
        return requestInfo;
    }


    /**
     * Sets the requestInfo value for this GetObjects.
     * 
     * @param requestInfo
     */
    public void setRequestInfo(java.lang.String[] requestInfo) {
        this.requestInfo = requestInfo;
    }


    /**
     * Gets the haveVersions value for this GetObjects.
     * 
     * @return haveVersions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion[] getHaveVersions() {
        return haveVersions;
    }


    /**
     * Sets the haveVersions value for this GetObjects.
     * 
     * @param haveVersions
     */
    public void setHaveVersions(com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion[] haveVersions) {
        this.haveVersions = haveVersions;
    }


    /**
     * Gets the areas value for this GetObjects.
     * 
     * @return areas
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AreaType[] getAreas() {
        return areas;
    }


    /**
     * Sets the areas value for this GetObjects.
     * 
     * @param areas
     */
    public void setAreas(com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
        this.areas = areas;
    }


    /**
     * Gets the editionId value for this GetObjects.
     * 
     * @return editionId
     */
    public java.lang.String getEditionId() {
        return editionId;
    }


    /**
     * Sets the editionId value for this GetObjects.
     * 
     * @param editionId
     */
    public void setEditionId(java.lang.String editionId) {
        this.editionId = editionId;
    }


    /**
     * Gets the supportedContentSources value for this GetObjects.
     * 
     * @return supportedContentSources
     */
    public java.lang.String[] getSupportedContentSources() {
        return supportedContentSources;
    }


    /**
     * Sets the supportedContentSources value for this GetObjects.
     * 
     * @param supportedContentSources
     */
    public void setSupportedContentSources(java.lang.String[] supportedContentSources) {
        this.supportedContentSources = supportedContentSources;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetObjects)) return false;
        GetObjects other = (GetObjects) obj;
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
            ((this.IDs==null && other.getIDs()==null) || 
             (this.IDs!=null &&
              java.util.Arrays.equals(this.IDs, other.getIDs()))) &&
            this.lock == other.isLock() &&
            ((this.rendition==null && other.getRendition()==null) || 
             (this.rendition!=null &&
              this.rendition.equals(other.getRendition()))) &&
            ((this.requestInfo==null && other.getRequestInfo()==null) || 
             (this.requestInfo!=null &&
              java.util.Arrays.equals(this.requestInfo, other.getRequestInfo()))) &&
            ((this.haveVersions==null && other.getHaveVersions()==null) || 
             (this.haveVersions!=null &&
              java.util.Arrays.equals(this.haveVersions, other.getHaveVersions()))) &&
            ((this.areas==null && other.getAreas()==null) || 
             (this.areas!=null &&
              java.util.Arrays.equals(this.areas, other.getAreas()))) &&
            ((this.editionId==null && other.getEditionId()==null) || 
             (this.editionId!=null &&
              this.editionId.equals(other.getEditionId()))) &&
            ((this.supportedContentSources==null && other.getSupportedContentSources()==null) || 
             (this.supportedContentSources!=null &&
              java.util.Arrays.equals(this.supportedContentSources, other.getSupportedContentSources())));
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
        if (getIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        _hashCode += (isLock() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getRendition() != null) {
            _hashCode += getRendition().hashCode();
        }
        if (getRequestInfo() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestInfo());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestInfo(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getHaveVersions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getHaveVersions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getHaveVersions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
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
        if (getEditionId() != null) {
            _hashCode += getEditionId().hashCode();
        }
        if (getSupportedContentSources() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSupportedContentSources());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSupportedContentSources(), i);
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
        new org.apache.axis.description.TypeDesc(GetObjects.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjects"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("IDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lock");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Lock"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rendition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rendition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("haveVersions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "HaveVersions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectVersion"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectVersion"));
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
        elemField.setFieldName("editionId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EditionId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("supportedContentSources");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SupportedContentSources"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
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
